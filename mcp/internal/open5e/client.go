package open5e

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strings"
	"time"
)

type Client struct {
	BaseURL    string
	HTTP       *http.Client
	SRDDocKeys []string
}

func NewClient() *Client {
	return &Client{
		BaseURL:    DefaultBaseURL,
		HTTP:       &http.Client{Timeout: 15 * time.Second},
		SRDDocKeys: SRDDocuments(),
	}
}

type SearchHit struct {
	RuleKey      string  `json:"rule_key,omitempty"`
	Name         string  `json:"name"`
	ObjectModel  string  `json:"object_model"`
	DocumentKey  string  `json:"document_key"`
	DocumentName string  `json:"document_name"`
	Snippet      string  `json:"snippet"`
	MatchScore   float64 `json:"match_score,omitempty"`
}

type SearchResult struct {
	Query     string      `json:"query"`
	SRDFilter []string    `json:"srd_filter"`
	Count     int         `json:"count"`
	Results   []SearchHit `json:"results"`
}

type RuleSection struct {
	Key      string `json:"key"`
	Name     string `json:"name"`
	Desc     string `json:"desc"`
	Document string `json:"document"`
	Ruleset  string `json:"ruleset,omitempty"`
}

type paginatedSearch struct {
	Count   int `json:"count"`
	Results []struct {
		Document struct {
			Key  string `json:"key"`
			Name string `json:"name"`
		} `json:"document"`
		ObjectPK     string  `json:"object_pk"`
		ObjectName   string  `json:"object_name"`
		ObjectModel  string  `json:"object_model"`
		Text         string  `json:"text"`
		MatchScore   float64 `json:"match_score"`
	} `json:"results"`
}

func (c *Client) SearchRulesReference(ctx context.Context, query string, limit int) (SearchResult, error) {
	query = strings.TrimSpace(query)
	if query == "" {
		return SearchResult{}, fmt.Errorf("query required")
	}
	if limit <= 0 {
		limit = 10
	}
	if limit > 25 {
		limit = 25
	}

	allowed := c.srdDocs()
	u, err := url.Parse(strings.TrimRight(c.baseURL(), "/") + "/search/")
	if err != nil {
		return SearchResult{}, err
	}
	q := u.Query()
	q.Set("query", query)
	q.Set("limit", fmt.Sprintf("%d", limit*3)) // over-fetch; filter SRD client-side
	u.RawQuery = q.Encode()

	body, err := c.get(ctx, u.String())
	if err != nil {
		return SearchResult{}, err
	}
	defer body.Close()

	var page paginatedSearch
	if err := json.NewDecoder(body).Decode(&page); err != nil {
		return SearchResult{}, fmt.Errorf("decode search: %w", err)
	}

	hits := make([]SearchHit, 0, limit)
	for _, r := range page.Results {
		if !allowsDocument(r.Document.Key, allowed) {
			continue
		}
		hit := SearchHit{
			Name:         r.ObjectName,
			ObjectModel:  r.ObjectModel,
			DocumentKey:  r.Document.Key,
			DocumentName: r.Document.Name,
			Snippet:      truncate(r.Text, 480),
			MatchScore:   r.MatchScore,
		}
		if r.ObjectModel == "Rule" && r.ObjectPK != "" {
			hit.RuleKey = r.ObjectPK
		}
		hits = append(hits, hit)
		if len(hits) >= limit {
			break
		}
	}

	return SearchResult{
		Query:     query,
		SRDFilter: allowed,
		Count:     len(hits),
		Results:   hits,
	}, nil
}

func (c *Client) GetRulesSection(ctx context.Context, key string) (RuleSection, error) {
	key = strings.TrimSpace(key)
	if key == "" {
		return RuleSection{}, fmt.Errorf("key required")
	}

	u := strings.TrimRight(c.baseURL(), "/") + "/rules/" + url.PathEscape(key) + "/"
	body, err := c.get(ctx, u)
	if err != nil {
		return RuleSection{}, err
	}
	defer body.Close()

	var rule RuleSection
	if err := json.NewDecoder(body).Decode(&rule); err != nil {
		return RuleSection{}, fmt.Errorf("decode rule: %w", err)
	}
	if rule.Key == "" {
		return RuleSection{}, fmt.Errorf("rule not found: %s", key)
	}
	if !allowsDocument(rule.Document, c.srdDocs()) {
		return RuleSection{}, fmt.Errorf("rule %s is outside configured SRD filter (%v)", key, c.srdDocs())
	}
	return rule, nil
}

func (c *Client) baseURL() string {
	if strings.TrimSpace(c.BaseURL) == "" {
		return DefaultBaseURL
	}
	return c.BaseURL
}

func (c *Client) srdDocs() []string {
	if len(c.SRDDocKeys) > 0 {
		return c.SRDDocKeys
	}
	return SRDDocuments()
}

func (c *Client) get(ctx context.Context, rawURL string) (io.ReadCloser, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, rawURL, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "worldkeep-mcp/1.7")

	httpClient := c.HTTP
	if httpClient == nil {
		httpClient = http.DefaultClient
	}
	resp, err := httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("open5e request: %w", err)
	}
	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		defer resp.Body.Close()
		b, _ := io.ReadAll(io.LimitReader(resp.Body, 512))
		return nil, fmt.Errorf("open5e HTTP %d: %s", resp.StatusCode, strings.TrimSpace(string(b)))
	}
	return resp.Body, nil
}

func truncate(s string, max int) string {
	s = strings.TrimSpace(s)
	if len(s) <= max {
		return s
	}
	return s[:max] + "…"
}

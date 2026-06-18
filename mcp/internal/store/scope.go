package store

import "strings"

type ReadScope string

const (
	ScopeParty ReadScope = "party"
	ScopeDM    ReadScope = "dm"
)

func ParseScope(s string) ReadScope {
	if strings.EqualFold(strings.TrimSpace(s), "dm") {
		return ScopeDM
	}
	return ScopeParty
}

func (sc ReadScope) IncludesDMOnly() bool {
	return sc == ScopeDM
}

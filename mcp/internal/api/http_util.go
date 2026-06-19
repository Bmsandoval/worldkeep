package api

import (
	"encoding/json"
	"net/http"
	"net/url"
	"strconv"
	"strings"
)

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(v)
}

func writeError(w http.ResponseWriter, err *Error) {
	if err == nil {
		return
	}
	writeJSON(w, err.Status, map[string]string{
		"error":   err.Code,
		"message": err.Message,
	})
}

func methodNotAllowed(w http.ResponseWriter, allowed ...string) {
	w.Header().Set("Allow", strings.Join(allowed, ", "))
	writeError(w, &Error{
		Status:  http.StatusMethodNotAllowed,
		Code:    "method_not_allowed",
		Message: "method not allowed",
	})
}

func queryInt(q url.Values, key string, def int) int {
	raw := strings.TrimSpace(q.Get(key))
	if raw == "" {
		return def
	}
	n, err := strconv.Atoi(raw)
	if err != nil {
		return def
	}
	return n
}

func queryBool(q url.Values, key string) bool {
	raw := strings.ToLower(strings.TrimSpace(q.Get(key)))
	return raw == "1" || raw == "true" || raw == "yes"
}

func corsMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Authorization, Content-Type")
		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusNoContent)
			return
		}
		next.ServeHTTP(w, r)
	})
}

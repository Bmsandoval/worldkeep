package api

import "fmt"

type Error struct {
	Status  int
	Code    string
	Message string
}

func (e *Error) Error() string {
	return e.Message
}

func errBadRequest(code, msg string) *Error {
	return &Error{Status: 400, Code: code, Message: msg}
}

func errForbidden(msg string) *Error {
	return &Error{Status: 403, Code: "forbidden", Message: msg}
}

func errNotFound(msg string) *Error {
	return &Error{Status: 404, Code: "not_found", Message: msg}
}

func errInternal(err error) *Error {
	return &Error{Status: 500, Code: "internal", Message: fmt.Sprintf("internal error: %v", err)}
}

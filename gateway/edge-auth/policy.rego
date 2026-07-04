package novacloud.edge.auth

default allow := false

allow if {
    input.jwt.valid == true
    input.jwt.claims.org == input.resource.org
    required := input.route.scopes[_]
    granted_scope(required)
}

granted_scope(required) if {
    input.jwt.claims.scopes[_] == required
}

granted_scope(required) if {
    endswith(required, ".read")
    input.jwt.claims.scopes[_] == "*.read"
}

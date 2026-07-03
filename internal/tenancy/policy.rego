package novacloud.tenancy

default allow := false

allow if {
    input.subject.organization_id == input.resource.organization_id
    not input.resource.deleted
}

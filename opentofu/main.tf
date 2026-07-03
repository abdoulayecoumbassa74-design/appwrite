terraform {
  required_version = ">= 1.6.0"
}

variable "namespace" {
  type    = string
  default = "novacloud"
}

output "namespace" {
  value = var.namespace
}

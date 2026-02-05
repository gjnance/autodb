variable "location" {
  description = "Azure region for resources"
  type        = string
  default     = "East US"
}

variable "environment" {
  description = "Environment name (dev, staging, prod)"
  type        = string
  default     = "dev"
}

variable "postgres_admin_username" {
  description = "PostgreSQL administrator username"
  type        = string
  default     = "autodb_admin"
}

variable "postgres_admin_password" {
  description = "PostgreSQL administrator password"
  type        = string
  sensitive   = true
}

variable "app_secret_key" {
  description = "Secret key for session signing"
  type        = string
  sensitive   = true
  default     = "change-me-in-production"
}

variable "enable_auth" {
  description = "Enable Azure AD authentication"
  type        = bool
  default     = false
}

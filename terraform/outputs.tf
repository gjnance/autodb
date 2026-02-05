output "resource_group_name" {
  description = "Resource group name"
  value       = azurerm_resource_group.main.name
}

output "container_app_url" {
  description = "AutoDB application URL"
  value       = "https://${azurerm_container_app.main.ingress[0].fqdn}"
}

output "container_registry_login_server" {
  description = "Azure Container Registry login server"
  value       = azurerm_container_registry.main.login_server
}

output "container_registry_admin_username" {
  description = "Azure Container Registry admin username"
  value       = azurerm_container_registry.main.admin_username
}

output "container_registry_admin_password" {
  description = "Azure Container Registry admin password"
  value       = azurerm_container_registry.main.admin_password
  sensitive   = true
}

output "postgres_server_fqdn" {
  description = "PostgreSQL server FQDN"
  value       = azurerm_postgresql_flexible_server.main.fqdn
}

output "postgres_database_name" {
  description = "PostgreSQL database name"
  value       = azurerm_postgresql_flexible_server_database.autodb.name
}

output "container_app_name" {
  description = "Container App name"
  value       = azurerm_container_app.main.name
}

output "azure_ad_client_id" {
  description = "Azure AD application client ID (if auth enabled)"
  value       = var.enable_auth ? azuread_application.autodb[0].client_id : null
}

output "log_analytics_workspace_id" {
  description = "Log Analytics Workspace ID"
  value       = azurerm_log_analytics_workspace.main.id
}

# GitHub Actions Secrets - copy these values
output "github_secrets" {
  description = "Values to set as GitHub secrets for deployment"
  value = {
    ACR_LOGIN_SERVER   = azurerm_container_registry.main.login_server
    ACR_USERNAME       = azurerm_container_registry.main.admin_username
    CONTAINER_APP_NAME = azurerm_container_app.main.name
    RESOURCE_GROUP     = azurerm_resource_group.main.name
  }
}

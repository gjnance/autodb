# AutoDB - Azure Container Apps Infrastructure
# This Terraform configuration deploys:
# - Azure Container Registry (Basic tier)
# - PostgreSQL Flexible Server (B_Standard_B1ms)
# - Azure Container Apps Environment
# - Azure Container App with scale-to-zero
# - Log Analytics Workspace
# - Virtual Network with private subnets

terraform {
  required_version = ">= 1.1.0"
  required_providers {
    azurerm = {
      source  = "hashicorp/azurerm"
      version = "~> 3.85.0"
    }
    azuread = {
      source  = "hashicorp/azuread"
      version = "~> 2.47.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.6.0"
    }
  }

  # Uncomment to use Terraform Cloud
  # cloud {
  #   organization = "Nance"
  #   workspaces {
  #     name = "autodb-dev"
  #   }
  # }
}

provider "azurerm" {
  features {}
}

provider "azuread" {}

provider "random" {}

# Random suffix for unique resource names
resource "random_string" "suffix" {
  length  = 6
  special = false
  upper   = false
}

locals {
  resource_suffix = random_string.suffix.result
  common_tags = {
    Application = "AutoDB"
    Environment = var.environment
    ManagedBy   = "Terraform"
  }
}

# Resource Group
resource "azurerm_resource_group" "main" {
  name     = "rg-autodb-${var.environment}"
  location = var.location
  tags     = local.common_tags
}

# Log Analytics Workspace
resource "azurerm_log_analytics_workspace" "main" {
  name                = "log-autodb-${local.resource_suffix}"
  location            = azurerm_resource_group.main.location
  resource_group_name = azurerm_resource_group.main.name
  sku                 = "PerGB2018"
  retention_in_days   = 30
  tags                = local.common_tags
}

# Virtual Network
resource "azurerm_virtual_network" "main" {
  name                = "vnet-autodb-${var.environment}"
  address_space       = ["10.0.0.0/16"]
  location            = azurerm_resource_group.main.location
  resource_group_name = azurerm_resource_group.main.name
  tags                = local.common_tags
}

# Subnet for Container Apps
resource "azurerm_subnet" "container_apps" {
  name                 = "snet-container-apps"
  resource_group_name  = azurerm_resource_group.main.name
  virtual_network_name = azurerm_virtual_network.main.name
  address_prefixes     = ["10.0.1.0/23"]

  delegation {
    name = "container-apps-delegation"
    service_delegation {
      name    = "Microsoft.App/environments"
      actions = ["Microsoft.Network/virtualNetworks/subnets/join/action"]
    }
  }
}

# Subnet for PostgreSQL
resource "azurerm_subnet" "postgres" {
  name                 = "snet-postgres"
  resource_group_name  = azurerm_resource_group.main.name
  virtual_network_name = azurerm_virtual_network.main.name
  address_prefixes     = ["10.0.4.0/24"]
  service_endpoints    = ["Microsoft.Storage"]

  delegation {
    name = "postgres-delegation"
    service_delegation {
      name = "Microsoft.DBforPostgreSQL/flexibleServers"
      actions = [
        "Microsoft.Network/virtualNetworks/subnets/join/action",
      ]
    }
  }
}

# Private DNS Zone for PostgreSQL
resource "azurerm_private_dns_zone" "postgres" {
  name                = "privatelink.postgres.database.azure.com"
  resource_group_name = azurerm_resource_group.main.name
  tags                = local.common_tags
}

resource "azurerm_private_dns_zone_virtual_network_link" "postgres" {
  name                  = "postgres-vnet-link"
  private_dns_zone_name = azurerm_private_dns_zone.postgres.name
  virtual_network_id    = azurerm_virtual_network.main.id
  resource_group_name   = azurerm_resource_group.main.name
}

# Azure Container Registry
resource "azurerm_container_registry" "main" {
  name                = "acrautodb${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.main.name
  location            = azurerm_resource_group.main.location
  sku                 = "Basic"
  admin_enabled       = true
  tags                = local.common_tags
}

# PostgreSQL Flexible Server
resource "azurerm_postgresql_flexible_server" "main" {
  name                   = "psql-autodb-${local.resource_suffix}"
  resource_group_name    = azurerm_resource_group.main.name
  location               = azurerm_resource_group.main.location
  version                = "15"
  delegated_subnet_id    = azurerm_subnet.postgres.id
  private_dns_zone_id    = azurerm_private_dns_zone.postgres.id
  administrator_login    = var.postgres_admin_username
  administrator_password = var.postgres_admin_password
  zone                   = "1"

  storage_mb = 32768

  sku_name = "B_Standard_B1ms"

  backup_retention_days        = 7
  geo_redundant_backup_enabled = false

  tags = local.common_tags

  depends_on = [azurerm_private_dns_zone_virtual_network_link.postgres]
}

# PostgreSQL Database
resource "azurerm_postgresql_flexible_server_database" "autodb" {
  name      = "autodb"
  server_id = azurerm_postgresql_flexible_server.main.id
  charset   = "utf8"
  collation = "en_US.utf8"
}

# Container Apps Environment
resource "azurerm_container_app_environment" "main" {
  name                       = "cae-autodb-${var.environment}"
  location                   = azurerm_resource_group.main.location
  resource_group_name        = azurerm_resource_group.main.name
  log_analytics_workspace_id = azurerm_log_analytics_workspace.main.id
  infrastructure_subnet_id   = azurerm_subnet.container_apps.id

  tags = local.common_tags
}

# Azure AD App Registration (for OAuth2)
resource "azuread_application" "autodb" {
  count        = var.enable_auth ? 1 : 0
  display_name = "AutoDB-${var.environment}"

  web {
    redirect_uris = [
      "https://${azurerm_container_app.main.ingress[0].fqdn}/auth/callback",
      "http://localhost:8000/auth/callback"
    ]

    implicit_grant {
      id_token_issuance_enabled = true
    }
  }

  required_resource_access {
    resource_app_id = "00000003-0000-0000-c000-000000000000" # Microsoft Graph

    resource_access {
      id   = "e1fe6dd8-ba31-4d61-89e7-88639da4683d" # User.Read
      type = "Scope"
    }
  }
}

resource "azuread_application_password" "autodb" {
  count          = var.enable_auth ? 1 : 0
  application_id = azuread_application.autodb[0].id
  display_name   = "AutoDB Client Secret"
}

# Container App
resource "azurerm_container_app" "main" {
  name                         = "ca-autodb-${var.environment}"
  container_app_environment_id = azurerm_container_app_environment.main.id
  resource_group_name          = azurerm_resource_group.main.name
  revision_mode                = "Single"

  template {
    container {
      name   = "autodb"
      image  = "${azurerm_container_registry.main.login_server}/autodb:latest"
      cpu    = 0.25
      memory = "0.5Gi"

      env {
        name  = "DATABASE_URL"
        value = "postgresql+asyncpg://${var.postgres_admin_username}:${var.postgres_admin_password}@${azurerm_postgresql_flexible_server.main.fqdn}:5432/autodb"
      }

      env {
        name        = "SECRET_KEY"
        secret_name = "secret-key"
      }

      dynamic "env" {
        for_each = var.enable_auth ? [1] : []
        content {
          name  = "AZURE_CLIENT_ID"
          value = azuread_application.autodb[0].client_id
        }
      }

      dynamic "env" {
        for_each = var.enable_auth ? [1] : []
        content {
          name        = "AZURE_CLIENT_SECRET"
          secret_name = "azure-client-secret"
        }
      }

      dynamic "env" {
        for_each = var.enable_auth ? [1] : []
        content {
          name  = "AZURE_TENANT_ID"
          value = data.azuread_client_config.current.tenant_id
        }
      }

      dynamic "env" {
        for_each = var.enable_auth ? [1] : []
        content {
          name  = "AZURE_REDIRECT_URI"
          value = "https://${azurerm_container_app.main.ingress[0].fqdn}/auth/callback"
        }
      }

      liveness_probe {
        transport = "HTTP"
        path      = "/health"
        port      = 8000
      }

      readiness_probe {
        transport = "HTTP"
        path      = "/health"
        port      = 8000
      }
    }

    min_replicas = 0
    max_replicas = 3

    http_scale_rule {
      name                = "http-scaling"
      concurrent_requests = 50
    }
  }

  ingress {
    external_enabled = true
    target_port      = 8000
    transport        = "http"

    traffic_weight {
      latest_revision = true
      percentage      = 100
    }
  }

  registry {
    server               = azurerm_container_registry.main.login_server
    username             = azurerm_container_registry.main.admin_username
    password_secret_name = "acr-password"
  }

  secret {
    name  = "acr-password"
    value = azurerm_container_registry.main.admin_password
  }

  secret {
    name  = "secret-key"
    value = var.app_secret_key
  }

  dynamic "secret" {
    for_each = var.enable_auth ? [1] : []
    content {
      name  = "azure-client-secret"
      value = azuread_application_password.autodb[0].value
    }
  }

  tags = local.common_tags

  lifecycle {
    ignore_changes = [
      template[0].container[0].image,
    ]
  }
}

# Data source for current Azure AD configuration
data "azuread_client_config" "current" {}

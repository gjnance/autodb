# Configure the Azure provider
# See terraform registry for additional providers
# https://registry.terraform.io/

terraform {
  required_version = ">= 1.1.0"
  required_providers {
    azurerm = {
      source  = "hashicorp/azurerm"
      version = "~> 3.0.2"
    }
  }
  cloud {
    organization = "Nance"
    workspaces {
      name = "AutoDB"
    }
  }
}

provider "azurerm" {
  features {}
}

resource "azurerm_resource_group" "autodb" {
  name     = "autodb-resource-group"
  location = "West US 2"
  tags = {
    Environment = "AutoDB Resource Group"
    Team = "Team Terminix"
  }
}

resource "azurerm_virtual_network" "autodb" {
  name                = "autodb-network"
  address_space       = ["10.0.0.0/16"]
  location            = azurerm_resource_group.autodb.location
  resource_group_name = azurerm_resource_group.autodb.name
}

resource "azurerm_subnet" "autodb" {
  name                 = "internal"
  resource_group_name  = azurerm_resource_group.autodb.name
  virtual_network_name = azurerm_virtual_network.autodb.name
  address_prefixes     = ["10.0.0.0/24"]
}

resource "azurerm_network_interface" "autodb" {
  name                = "autodb-nic"
  location            = azurerm_resource_group.autodb.location
  resource_group_name = azurerm_resource_group.autodb.name

  ip_configuration {
    name                          = "internal"
    subnet_id                     = azurerm_subnet.autodb.id
    private_ip_address_allocation = "Static"
    private_ip_address            = "10.0.0.10"
    public_ip_address_id          = azurerm_public_ip.autodb.id
  }
}

resource "azurerm_public_ip" "autodb" {
  name                = "autodb-public-ip"
  location            = azurerm_resource_group.autodb.location
  resource_group_name = azurerm_resource_group.autodb.name

  allocation_method       = "Dynamic"
  idle_timeout_in_minutes = 30
}

resource "tls_private_key" "autodb" {
  algorithm = "RSA"
  rsa_bits = 4096
}

resource "azurerm_linux_virtual_machine" "autodb" {
  name                = "autodb-virtual-machine"
  resource_group_name = azurerm_resource_group.autodb.name
  location            = azurerm_resource_group.autodb.location
  size                = "Standard_B1s"
  admin_username      = var.vm_admin_username

  network_interface_ids = [
    azurerm_network_interface.autodb.id,
  ]

  admin_ssh_key {
    username   = "adminuser"
    public_key = tls_private_key.autodb.public_key_openssh
  }

  os_disk {
    caching              = "ReadWrite"
    storage_account_type = "Standard_LRS"
  }

  source_image_reference {
    publisher = "Canonical"
    offer     = "0001-com-ubuntu-server-focal"
    sku       = "20_04-lts"
    version   = "latest"
  }

  provisioner "file" {
    connection {
      type        = "ssh"
      user        = var.vm_admin_username
      host        = azurerm_public_ip.autodb.ip_address
      private_key = tls_private_key.autodb.private_key_pem
      agent       = false
      timeout     = "2m"
    }
    source      = "vm-scripts/vm-provision.sh"
    destination = "/tmp/vm-provision.sh"
  }

    # Install apache2 on virtual machine and move index.php to configured location
  provisioner "remote-exec" {
    connection {
      type        = "ssh"
      user        = var.vm_admin_username
      host        = azurerm_public_ip.main.ip_address
      private_key = tls_private_key.autodb.private_key_pem
      agent       = false
      timeout     = "2m"
    }

    inline = [
      "/tmp/vm-provision.sh"
    ]
  }
}

resource "azurerm_network_security_group" "autodb" {
  name                = "autodb-security-group"
  location            = azurerm_resource_group.autodb.location
  resource_group_name = azurerm_resource_group.autodb.name

  security_rule {
    name                       = "SSH"
    priority                   = 300
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_address_prefix      = "*"
    source_port_range          = "*"
    destination_address_prefix = "*"
    destination_port_range     = "22"
  }
  security_rule {
    name                       = "HTTP"
    priority                   = 320
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_address_prefix      = "*"
    source_port_range          = "*"
    destination_address_prefix = "*"
    destination_port_range     = "80"
  }
}

resource "azurerm_network_interface_security_group_association" "autodb" {
  network_interface_id         = azurerm_network_interface.autodb.id
  network_security_group_id    = azurerm_network_security_group.autodb.id
}

# Azure MySQL Server
# resource "azurerm_mysql_server" "main" {
#   name                              = "${var.prefix}-mysqlserver"
#   location                          = azurerm_resource_group.main.location
#   resource_group_name               = azurerm_resource_group.main.name
#   administrator_login               = var.mysql_administrator_login
#   administrator_login_password      = var.mysql_administrator_login_password
#   sku_name                          = "B_Gen5_2"
#   storage_mb                        = 5120
#   version                           = "5.7"
#   auto_grow_enabled                 = true
#   backup_retention_days             = 7
#   geo_redundant_backup_enabled      = false
#   //public_network_access_enabled   = false
#   ssl_enforcement_enabled           = true
#   ssl_minimal_tls_version_enforced  = "TLS1_2"
# }
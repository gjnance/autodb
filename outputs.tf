output "resource_group_id" {
  value = azurerm_resource_group.autodb.id
}
output "virtual_network_id" {
  value = azurerm_virtual_network.autodb.id
}
output "public_ip_address" {
  description = "VM Public IP Address:"
  value = azurerm_public_ip.autodb.ip_address
}
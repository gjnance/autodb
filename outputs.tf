output "resource_group_id" {
  value = azurerm_resource_group.example.id
}
output "virtual_network_id" {
  value = azurerm_virtual_network.example.id
}
output "public_ip_address" {
  value = data.azurerm_public_ip.example.ip_address
}
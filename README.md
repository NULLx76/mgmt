# mgmt
mgmt is VM Mangement software.
It has 2 packages:

* mgmt Server
* mgmt Client

as the names suggest the client package is installed on the client server and sends information to the server,
the server package recieves this information and may send commands to connected clients.

## Connection
The connection is established through some sort of pre-agreed upon password/api-key, there is one key per server.

## Client ID
Clients can be IDed through either a UUID generated upon first start.
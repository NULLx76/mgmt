package main

import "net"
import "fmt"
import "bufio"
import (
	"strings"
	"encoding/json"
)

func main() {

	fmt.PrintLn("Starting...")

	// Listen on all interfaces
	ln, _ := net.Listen("tcp", ":7669")

	// Accept all connections
	conn, _ := ln.Accept()

	// Create new json decoder
	d := json.NewDecoder(conn)

	var msg coordinate

	err := d.Decode(&msg)
	fmt.Print(msg,err)

	conn.Close()

}

package service

import (
	"sync"

	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/store"
	"go.mau.fi/whatsmeow/types/events"
	waLog "go.mau.fi/whatsmeow/util/log"
)

type ClientStore struct {
	clients map[string]*whatsmeow.Client
	mx      sync.RWMutex
}

func New() *ClientStore {
	return &ClientStore{
		clients: make(map[string]*whatsmeow.Client),
	}
}

func (c *ClientStore) NewClient(device *store.Device) *whatsmeow.Client {
	client := whatsmeow.NewClient(device, waLog.Stdout("Client", "INFO", false))

	client.AddEventHandler(func(evt any) {
		switch evt.(type) {
		case *events.Connected:
			c.Store(client.Store.ID.String(), client)
			client.Log.Infof("Client stored")
		case *events.LoggedOut:
			c.Delete(client.Store.ID.String())
			client.Log.Infof("Client deleted")
		}
	})

	return client
}

func (c *ClientStore) Load(id string) (*whatsmeow.Client, bool) {
	c.mx.RLock()
	defer c.mx.RUnlock()

	client, found := c.clients[id]
	return client, found
}

func (c *ClientStore) Store(id string, client *whatsmeow.Client) {
	c.mx.Lock()
	defer c.mx.Unlock()

	c.clients[id] = client
}

func (c *ClientStore) Delete(id string) {
	c.mx.Lock()
	defer c.mx.Unlock()

	delete(c.clients, id)
}

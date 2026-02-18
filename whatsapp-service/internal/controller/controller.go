package controller

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"konversia-whatsapp-service/internal/service"
	"log"
	"net/http"
	"net/url"
	"os"
	"reflect"
	"strings"
	"time"

	"github.com/labstack/echo/v5"
	_ "github.com/lib/pq"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/store/sqlstore"
	waLog "go.mau.fi/whatsmeow/util/log"
)

type Controller struct {
	devices *sqlstore.Container
	clients *service.ClientStore
}

func NewController() *Controller {
	defer log.Println("Restoring finished")

	ctx, cancel := context.WithTimeout(context.Background(), time.Minute)
	defer cancel()

	dbLog := waLog.Stdout("Database", "INFO", false)
	container, err := sqlstore.New(ctx, "postgres", os.Getenv("DATABASE_URL"), dbLog)
	if err != nil {
		panic(err)
	}

	return &Controller{
		devices: container,
		clients: service.New(),
	}
}

func (ctrl *Controller) RestoreAll() {
	ctx := context.Background()

	devices, err := ctrl.devices.GetAllDevices(ctx)
	if err != nil {
		panic(err)
	}

	for i, device := range devices {
		log.Printf("Restoring %d of %d devices\n", i+1, len(devices))

		client := ctrl.clients.NewClient(device)
		if err = client.Connect(); err != nil {
			log.Println("error reconnecting client", err.Error())
			continue
		}

		client.AddEventHandler(func(evt any) {
			NotifyWebhook(client.Store.ID.String(), evt)
		})
	}
}

func (ctrl *Controller) Middleware(next echo.HandlerFunc) echo.HandlerFunc {
	return func(ctx *echo.Context) error {
		id := ctx.Request().Header.Get("X-NUMBER-ID")
		if id == "" {
			return echo.NewHTTPError(http.StatusBadRequest, "X-NUMBER-ID header is required")
		}

		client, found := ctrl.clients.Load(id)
		if !found {
			client = ctrl.clients.NewClient(ctrl.devices.NewDevice())
		}

		ctx.Set("client", client)

		return next(ctx)
	}
}

func (ctrl *Controller) Create(ctx *echo.Context) error {
	client := ctx.Get("client").(*whatsmeow.Client)

	c, err := client.GetQRChannel(ctx.Request().Context())
	if err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	if err = client.Connect(); err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	for evt := range c {
		if evt.Event == "code" {
			fmt.Println("https://quickchart.io/qr?text=" + url.QueryEscape(evt.Code))
		}

		NotifyWebhook(ctx.Request().Header.Get("X-NUMBER-ID"), evt)
	}

	client.AddEventHandler(func(evt any) {
		NotifyWebhook(client.Store.ID.String(), evt)
	})

	return ctx.JSON(http.StatusOK, map[string]any{
		"ID": client.Store.ID.String(),
	})
}

func (ctrl *Controller) Status(ctx *echo.Context) error {
	client := ctx.Get("client").(*whatsmeow.Client)

	return ctx.JSON(http.StatusOK, map[string]any{
		"IsConnected": client.IsConnected(),
		"IsLoggedIn":  client.IsLoggedIn(),
	})
}

func (ctrl *Controller) Destroy(ctx *echo.Context) error {
	client := ctx.Get("client").(*whatsmeow.Client)

	if err := client.Logout(ctx.Request().Context()); err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	return ctx.NoContent(http.StatusOK)
}

func NotifyWebhook(id string, data any) {
	event := struct {
		ID   string
		Type string
		Data any
	}{
		ID:   id,
		Type: strings.SplitN(reflect.TypeOf(data).String(), ".", 2)[1],
		Data: data,
	}

	log.Printf("Notifying webhook %+v\n", event)

	webhookURL := os.Getenv("WEBHOOK_URL")
	if webhookURL == "" {
		panic("WEBHOOK_URL env variable is required")
	}

	var body bytes.Buffer
	if err := json.NewEncoder(&body).Encode(&event); err != nil {
		log.Println("error encoding event", err.Error())
		return
	}

	req, err := http.NewRequest("POST", webhookURL, &body)
	if err != nil {
		log.Println("error creating request", err.Error())
		return
	}

	req.Header.Set("Content-Type", "application/json")

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		log.Println("error sending request", err.Error())
		return
	}

	if res.StatusCode != http.StatusOK {
		log.Println("error sending request", res.Status)
		return
	}
}

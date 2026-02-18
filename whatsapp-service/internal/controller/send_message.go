package controller

import (
	"net/http"

	"github.com/labstack/echo/v5"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
)

func (ctrl *Controller) SendMessage(ctx *echo.Context) error {
	var req struct {
		To      string
		Message string
	}
	if err := ctx.Bind(&req); err != nil {
		return err
	}

	if req.To == "" {
		return echo.NewHTTPError(http.StatusBadRequest, "to is required")
	}

	if req.Message == "" {
		return echo.NewHTTPError(http.StatusBadRequest, "message is required")
	}

	client := ctx.Get("client").(*whatsmeow.Client)

	contacts, err := client.IsOnWhatsApp(ctx.Request().Context(), []string{req.To})
	if err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	if len(contacts) == 0 || !contacts[0].IsIn {
		return echo.NewHTTPError(http.StatusBadRequest, "contact is not on WhatsApp")
	}

	message := waE2E.Message{
		Conversation: &req.Message,
	}

	if _, err = client.SendMessage(ctx.Request().Context(), contacts[0].JID, &message); err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	return ctx.NoContent(http.StatusOK)
}

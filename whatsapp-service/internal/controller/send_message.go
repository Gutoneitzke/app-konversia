package controller

import (
	"context"
	"io"
	"net/http"

	"github.com/labstack/echo/v5"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
)

func (ctrl *Controller) SendMessage(ctx *echo.Context) error {
	var req struct {
		To      string
		Message *waE2E.Message
	}
	if err := ctx.Bind(&req); err != nil {
		return err
	}

	if req.To == "" {
		return echo.NewHTTPError(http.StatusBadRequest, "to is required")
	}

	if req.Message == nil {
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

	if err = fulfillMessage(ctx.Request().Context(), client, req.Message); err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	if _, err = client.SendMessage(ctx.Request().Context(), contacts[0].JID, req.Message); err != nil {
		return echo.NewHTTPError(http.StatusInternalServerError, err.Error())
	}

	return ctx.NoContent(http.StatusOK)
}

func fulfillMessage(ctx context.Context, client *whatsmeow.Client, message *waE2E.Message) error {
	uploaded, err := uploadToWhatsApp(ctx, client, message)
	if err != nil {
		return err
	}

	if uploaded == nil {
		return nil
	}

	if i := message.ImageMessage; i != nil {
		i.URL = &uploaded.URL
		i.DirectPath = &uploaded.DirectPath
		i.MediaKey = uploaded.MediaKey
		i.FileEncSHA256 = uploaded.FileEncSHA256
		i.FileSHA256 = uploaded.FileSHA256
		i.FileLength = &uploaded.FileLength
	} else if v := message.VideoMessage; v != nil {
		v.URL = &uploaded.URL
		v.DirectPath = &uploaded.DirectPath
		v.MediaKey = uploaded.MediaKey
		v.FileEncSHA256 = uploaded.FileEncSHA256
		v.FileSHA256 = uploaded.FileSHA256
		v.FileLength = &uploaded.FileLength
	} else if a := message.AudioMessage; a != nil {
		a.URL = &uploaded.URL
		a.DirectPath = &uploaded.DirectPath
		a.MediaKey = uploaded.MediaKey
		a.FileEncSHA256 = uploaded.FileEncSHA256
		a.FileSHA256 = uploaded.FileSHA256
		a.FileLength = &uploaded.FileLength
	} else if d := message.DocumentMessage; d != nil {
		d.URL = &uploaded.URL
		d.DirectPath = &uploaded.DirectPath
		d.MediaKey = uploaded.MediaKey
		d.FileEncSHA256 = uploaded.FileEncSHA256
		d.FileSHA256 = uploaded.FileSHA256
		d.FileLength = &uploaded.FileLength
	}

	return nil
}

func uploadToWhatsApp(ctx context.Context, client *whatsmeow.Client, message *waE2E.Message) (*whatsmeow.UploadResponse, error) {
	var url string
	var mediaType whatsmeow.MediaType
	if i := message.ImageMessage; i != nil {
		url = *i.URL
		mediaType = whatsmeow.MediaImage
	} else if v := message.VideoMessage; v != nil {
		url = *v.URL
		mediaType = whatsmeow.MediaVideo
	} else if a := message.AudioMessage; a != nil {
		url = *a.URL
		mediaType = whatsmeow.MediaAudio
	} else if d := message.DocumentMessage; d != nil {
		url = *d.URL
		mediaType = whatsmeow.MediaDocument
	} else {
		return nil, nil
	}

	r, err := downloadFromURL(ctx, url)
	if err != nil {
		return nil, nil
	}
	defer r.Close()

	uploaded, err := client.UploadReader(ctx, r, nil, mediaType)
	if err != nil {
		return nil, nil
	}

	return &uploaded, err
}

func downloadFromURL(ctx context.Context, url string) (io.ReadCloser, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return nil, err
	}

	res, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}

	if res.StatusCode != http.StatusOK {
		return nil, err
	}

	return res.Body, nil
}

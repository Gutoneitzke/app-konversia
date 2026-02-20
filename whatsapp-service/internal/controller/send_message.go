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
		return nil
	}

	r, err := downloadFromURL(ctx, url)
	if err != nil {
		return err
	}
	defer r.Close()

	resp, err := client.UploadReader(ctx, r, nil, mediaType)
	if err != nil {
		return err
	}

	if i := message.ImageMessage; i != nil {
		i.URL = &resp.URL
		i.DirectPath = &resp.DirectPath
		i.MediaKey = resp.MediaKey
		i.FileEncSHA256 = resp.FileEncSHA256
		i.FileSHA256 = resp.FileSHA256
		i.FileLength = &resp.FileLength
	} else if v := message.VideoMessage; v != nil {
		v.URL = &resp.URL
		v.DirectPath = &resp.DirectPath
		v.MediaKey = resp.MediaKey
		v.FileEncSHA256 = resp.FileEncSHA256
		v.FileSHA256 = resp.FileSHA256
		v.FileLength = &resp.FileLength
	} else if a := message.AudioMessage; a != nil {
		a.URL = &resp.URL
		a.DirectPath = &resp.DirectPath
		a.MediaKey = resp.MediaKey
		a.FileEncSHA256 = resp.FileEncSHA256
		a.FileSHA256 = resp.FileSHA256
		a.FileLength = &resp.FileLength
	} else if d := message.DocumentMessage; d != nil {
		d.URL = &resp.URL
		d.DirectPath = &resp.DirectPath
		d.MediaKey = resp.MediaKey
		d.FileEncSHA256 = resp.FileEncSHA256
		d.FileSHA256 = resp.FileSHA256
		d.FileLength = &resp.FileLength
	}

	return nil
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

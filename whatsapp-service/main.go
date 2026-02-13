package main

import (
	"fmt"
	"konversia-whatsapp-service/internal/controller"

	"github.com/labstack/echo/v5"
	"github.com/labstack/echo/v5/middleware"
	"go.mau.fi/whatsmeow/types/events"
)

func eventHandler(evt interface{}) {
	switch v := evt.(type) {
	case *events.Message:
		fmt.Println("Received a message!", v.Message.GetConversation())
	}
}

func main() {
	e := echo.New()
	e.Use(middleware.Recover(), middleware.RequestLogger(), middleware.CORS("*"), middleware.Gzip())

	ctrl := controller.NewController()
	ctrl.RestoreAll()

	wa := e.Group("/number", ctrl.Middleware)
	wa.POST("", ctrl.Create)
	wa.GET("", ctrl.Status)
	wa.DELETE("", ctrl.Destroy)
	wa.POST("/message", ctrl.SendMessage)

	if err := e.Start(":8080"); err != nil {
		panic(err)
	}
}

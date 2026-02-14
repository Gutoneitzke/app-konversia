package main

import (
	"konversia-whatsapp-service/internal/controller"
	"os"

	"github.com/labstack/echo/v5"
	"github.com/labstack/echo/v5/middleware"
)

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

	address := os.Getenv("ADDRESS")
	if address == "" {
		panic("ADDRESS env variable is required")
	}

	if err := e.Start(address); err != nil {
		panic(err)
	}
}

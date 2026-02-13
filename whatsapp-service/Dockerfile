FROM golang:1.25.7-alpine

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download && \
    go mod verify

COPY . .

RUN go build -o whatsapp-service

FROM alpine:3.19

WORKDIR /app

COPY --from=0 /app/whatsapp-service ./

CMD /app/whatsapp-service

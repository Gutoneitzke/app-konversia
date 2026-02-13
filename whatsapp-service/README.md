# WhatsApp Service

## Usage of

```shell
docker compose up
```

### Available args

- `-address` `string` Address to listen on (default `":8080"`)
- `-webhook` `string` Webhook URL
- `--help` Show help

## Endpoints

### Create

- **temporary-id** is a temporary unique identifier for the number

```http request
POST /number HTTP/1.1
X-Number-Id: {{temporary-id}}
```

### Status

- **number-id** is the unique identifier for the number created

```http request
GET /number HTTP/1.1
X-Number-Id: {{number-id}}
```

### Destroy

- **number-id** is the unique identifier for the number created

```http request
DELETE /number HTTP/1.1
X-Number-Id: {{number-id}}
```

### Send Message

- **number-id** is the unique identifier for the number created
- **to** is the phone number to send the message to
- **message** is the message to send

```http request
POST /number/message HTTP/1.1
Content-Type: application/json
X-Number-Id: {{number-id}}

{
	"To": "{{to}}",
	"Message": "{{message}}"
}
```
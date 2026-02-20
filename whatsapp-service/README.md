# WhatsApp Service

## Usage of

### Create `.env` file

```shell
cp .env.example .env
```

### Run the services

```shell
docker compose up
```

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
	"Message": {
	    "Conversation": "Hello, World!",
	    "ImageMessage": {
	        "Caption": "Hello, Image!",
	        "Mimetype": "image/png",
	        "URL": "https://website.com/image.png"
	    },
	    "VideoMessage": {
	        "Caption": "Hello, Video!",
	        "Mimetype": "video/mp4",
	        "URL": "https://website.com/video.mp4"
	    },
	    "AudioMessage": {
	        "Mimetype": "audio/mp3",
	        "URL": "https://website.com/audio.mp3",
	        "PTT": true
	    },
	    "DocumentMessage": {
	        "Title": "Hello, Document!",
	        "FileName": "document.pdf",
	        "Mimetype": "document/pdf",
	        "URL": "https://website.com/document.pdf"
	    }
	}
}
```
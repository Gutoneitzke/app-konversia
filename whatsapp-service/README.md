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
- **message** is the message to send; you should typically populate only one of the following primary content fields per
  request:
  - **Conversation**: A simple plain-text message.
  - **ImageMessage**:
    - **Caption**: A plain-text caption for the image. (optional)
    - **Mimetype**: The MIME type of the image.
    - **URL**: The URL of the image.
  - **VideoMessage**:
    - **Caption**: A plain-text caption for the video. (optional)
    - **Mimetype**: The MIME type of the video.
    - **URL**: The URL of the video.
  - **AudioMessage**:
    - **Mimetype**: The MIME type of the audio.
    - **URL**: The URL of the audio.
    - **PTT**: Whether the audio is a PTT message. (optional)
  - **DocumentMessage**:
    - **Title**: A plain-text title for the document. (optional)
    - **FileName**: The file name of the document. (optional)
    - **Mimetype**: The MIME type of the document.
    - **URL**: The URL of the document.

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
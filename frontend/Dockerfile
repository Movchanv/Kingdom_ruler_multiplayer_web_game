# syntax=docker/dockerfile:1

FROM node:22-alpin

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 5173

CMD ["sh", "-c", "[ -f .env ] || cp .env.example .env; npm run dev -- --host 0.0.0.0"]

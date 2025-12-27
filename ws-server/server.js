const express = require("express");
const WebSocket = require("ws");
const http = require("http");

const app = express();
app.use(express.json());

const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

let clients = [];

/* WebSocket */
wss.on("connection", (ws) => {
  clients.push(ws);
  console.log("WS connected:", clients.length);

  ws.on("close", () => {
    clients = clients.filter((c) => c !== ws);
  });
});

/* PHP gọi API này */
app.post("/notify", (req, res) => {
  const payload = req.body;

  clients.forEach((ws) => {
    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(payload));
    }
  });

  res.json({ ok: true });
});

server.listen(3000, "0.0.0.0", () => {
  console.log("Notify + WebSocket running on :3000");
});

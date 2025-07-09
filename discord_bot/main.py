import os
import asyncio # Import hier hinzugefügt
from fastapi import FastAPI, HTTPException, Security
from fastapi.security.api_key import APIKeyHeader
import discord
from dotenv import load_dotenv

# Umgebungsvariablen laden
load_dotenv()

DISCORD_BOT_TOKEN = os.getenv("DISCORD_BOT_TOKEN")
API_SECRET_KEY = os.getenv("API_SECRET_KEY")
GUILD_ID = int(os.getenv("GUILD_ID"))
ROLE_ID = int(os.getenv("ROLE_ID"))

if not all([DISCORD_BOT_TOKEN, API_SECRET_KEY, GUILD_ID, ROLE_ID]):
    print("Fehler: Nicht alle notwendigen Umgebungsvariablen sind gesetzt!")
    print("Benötigt: DISCORD_BOT_TOKEN, API_SECRET_KEY, GUILD_ID, ROLE_ID")
    exit(1)

# FastAPI App initialisieren
app = FastAPI(title="Echo Sol Dojo Verification Bot API", version="1.0.0")

# API Key Header definieren für die Sicherheit
api_key_header = APIKeyHeader(name="Authorization", auto_error=True)

# Discord Client initialisieren
intents = discord.Intents.default()
intents.members = True  # Wichtig, um Mitgliederinformationen abrufen zu können
client = discord.Client(intents=intents)

# Globale Variable für den Bot-Status
bot_ready = False

@client.event
async def on_ready():
    global bot_ready
    print(f'{client.user} ist jetzt mit Discord verbunden und bereit!')
    bot_ready = True
    # Optional: Spiel/Status des Bots setzen
    await client.change_presence(activity=discord.Game(name="Tenno verifizieren"))

async def get_api_key(api_key_header: str = Security(api_key_header)):
    """Überprüft den API-Key im Authorization Header."""
    # Erwartet "Bearer <API_SECRET_KEY>"
    if not api_key_header.startswith("Bearer "):
        raise HTTPException(status_code=403, detail="Ungültiges Authentifizierungsformat. Erwartet 'Bearer <token>'.")

    token = api_key_header.split(" ")[1]
    if token == API_SECRET_KEY:
        return token
    else:
        raise HTTPException(
            status_code=403, detail="Ungültiger oder fehlender API Key"
        )

@app.get("/verify-user/{discord_id}", tags=["Verification"])
async def verify_user(discord_id: int, api_key: str = Security(get_api_key)):
    """
    Überprüft die Mitgliedschaft und Rolle eines Discord-Nutzers auf dem Server.
    Benötigt einen gültigen API-Key im "Authorization: Bearer <key>" Header.
    """
    global bot_ready
    if not bot_ready:
        raise HTTPException(status_code=503, detail="Bot ist noch nicht bereit oder nicht mit Discord verbunden.")

    try:
        guild = client.get_guild(GUILD_ID)
        if not guild:
            print(f"Fehler: Server (Guild) mit ID {GUILD_ID} nicht gefunden.")
            raise HTTPException(status_code=500, detail="Serverkonfiguration fehlerhaft (Guild nicht gefunden).")

        member = await guild.fetch_member(discord_id) # fetch_member ist zuverlässiger als get_member
        if not member:
            return {"status": "NOT_ON_SERVER"}

        # Rolle prüfen
        # Die ROLE_ID muss ein Integer sein, da sie aus der .env als String kommt.
        # Dies wurde bereits beim Laden der Umgebungsvariablen sichergestellt (int()).
        role = guild.get_role(ROLE_ID)
        if not role:
            print(f"Fehler: Rolle mit ID {ROLE_ID} nicht auf dem Server {GUILD_ID} gefunden.")
            raise HTTPException(status_code=500, detail="Serverkonfiguration fehlerhaft (Rolle nicht gefunden).")

        if role in member.roles:
            return {"status": "SUCCESS"}
        else:
            return {"status": "NO_ROLE"}

    except discord.NotFound:
        # Tritt auf, wenn fetch_member den User nicht findet (weil er nicht auf dem Server ist)
        return {"status": "NOT_ON_SERVER"}
    except discord.Forbidden:
        print(f"Fehler: Dem Bot fehlen die nötigen Berechtigungen, um Mitgliederinformationen für Server {GUILD_ID} abzurufen.")
        raise HTTPException(status_code=500, detail="Bot-Berechtigungsfehler auf dem Discord Server.")
    except Exception as e:
        print(f"Ein unerwarteter Fehler ist aufgetreten: {e}")
        raise HTTPException(status_code=500, detail=f"Interne Serverfehler: {str(e)}")

@app.on_event("startup")
async def startup_event():
    """Startet den Discord-Bot-Client, wenn die FastAPI-Anwendung startet."""
    # Es ist wichtig, client.start() in einem separaten Task laufen zu lassen,
    # damit es nicht den Start von Uvicorn blockiert.
    # discord.py's client.start() ist eine blockierende Operation.
    # Daher verwenden wir client.login() und client.connect() für eine bessere Kontrolle
    # oder führen client.start() in einem asyncio Task aus.
    # Für FastAPI ist es oft besser, den Bot separat zu starten oder sehr vorsichtig zu integrieren.
    # Eine gängige Methode ist, den Bot im Hintergrund laufen zu lassen.
    print("FastAPI Startup: Versuche Discord Bot zu starten...")
    try:
        # Startet den Bot in einem Hintergrund-Task, damit Uvicorn nicht blockiert wird
        asyncio.create_task(client.start(DISCORD_BOT_TOKEN))
        # Kurze Pause, um dem Bot Zeit zum Verbinden zu geben, bevor Anfragen bearbeitet werden
        # Dies ist eine einfache Methode; für Produktion könnten robustere Checks nötig sein.
        # await asyncio.sleep(5) # Wartezeit, bis on_ready getriggert wird
        print("Discord Bot Start-Task wurde erstellt.")
    except Exception as e:
        print(f"Fehler beim Starten des Discord Bots: {e}")
        # Hier könnte man entscheiden, ob die FastAPI App trotzdem starten soll oder nicht.
        # Momentan würde sie starten, aber der Bot wäre nicht funktionsfähig.

@app.on_event("shutdown")
async def shutdown_event():
    """Schließt die Verbindung des Discord-Bot-Clients, wenn die FastAPI-Anwendung herunterfährt."""
    global bot_ready
    if bot_ready:
        print("FastAPI Shutdown: Schließe Discord Bot Verbindung...")
        await client.close()
        bot_ready = False
        print("Discord Bot Verbindung geschlossen.")

# Zum direkten Testen der FastAPI App (optional)
if __name__ == "__main__":
    import uvicorn
    import asyncio # erforderlich für client.start in startup_event
    print("Starte Uvicorn Server für Entwicklung...")
    # Der Bot muss vor Uvicorn gestartet werden oder wie oben im startup event
    # Für eine einfache Entwicklung kann man den Bot auch hier starten,
    # aber die Integration in startup/shutdown ist sauberer für FastAPI.

    # Hinweis: Die asyncio.create_task Methode in startup_event ist der bevorzugte Weg.
    # Wenn man den Bot hier separat starten würde, müsste man die Bot-Schleife und die
    # Uvicorn-Schleife manuell verwalten, was komplexer ist.

    uvicorn.run(app, host="0.0.0.0", port=8000)

timbitsLeft = int(input()) # Schritt 1: Eingabe lesen
totalCost = 0              # Schritt 2: Initialisieren

# Schritt 3: so viele große Boxen wie möglich kaufen
bigBoxes = int(timbitsLeft / 40)
totalCost = totalCost + bigBoxes * 6.19    # Preis aktualisieren
timbitsLeft = timbitsLeft - 40 * bigBoxes  # Noch übrige Timbits

if timbitsLeft >= 20:                # Schritt 4: können wir noch eine mittlere Box kaufen?
    totalCost = totalCost + 3.39
    timbitsLeft = timbitsLeft - 20
if timbitsLeft >= 10:                # Schritt 5: können wir noch eine kleine Box kaufen?
    totalCost = totalCost + 1.99
    timbitsLeft = timbitsLeft - 10

totalCost = totalCost + timbitsLeft * 0.20 # Schritt 6
print(totalCost)                           # Schritt 7

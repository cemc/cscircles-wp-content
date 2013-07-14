timbitsLeft = int(input()) # Schritt 1: Eingabe lesen

print('the input is', timbitsLeft)

totalCost = 0              # Schritt 2: Initialisieren

# Schritt 3: so viele große Boxen wie möglich kaufen
bigBoxes = timbitsLeft / 40
totalCost = totalCost + bigBoxes * 6.19    # Preis aktualisieren
timbitsLeft = timbitsLeft - 40 * bigBoxes  # Noch übrige Timbits

print('bigBoxes equals', bigBoxes)
print('totalCost equals', totalCost)
print('now timbitsLeft equals', timbitsLeft)

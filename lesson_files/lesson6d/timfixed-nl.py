timbitsResterend = int(input()) # stap 1: verkrijg de input
totaleKosten = 0  # stap 2: initialiseer de totale kosten

# stap 3: koop zoveel mogelijk grote dozen
groteDozen = int(timbitsResterend / 40)
totaleKosten = totaleKosten + groteDozen * 6.19  # update de totale prijs
timbitsResterend = timbitsResterend - 40 * groteDozen  # reken uit hoeveel timbits we nog steeds nodig hebben

if timbitsResterend >= 20:  # stap 4, kunnen we een middelgrote doos kopen?
    totaleKosten = totaleKosten + 3.39
    timbitsResterend = timbitsResterend - 20
if timbitsResterend >= 10: # stap 5, kunnen we een kleine doos kopen?
    totaleKosten = totaleKosten + 1.99
    timbitsResterend = timbitsResterend - 10

totaleKosten = totaleKosten + timbitsResterend * 0.20 # stap 6
print(totaleKosten)  # stap 7

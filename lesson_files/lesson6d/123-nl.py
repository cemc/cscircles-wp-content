timbitsResterend = int(input())  # stap 1: verkrijg de input

print('de input is', timbitsResterend)

totaleKosten = 0  # stap 2: initialiseer de totale kosten

# stap 3: koop zoveel mogelijk grote dozen
groteDozen = int(timbitsResterend / 40)
totaleKosten = totaleKosten + groteDozen * 6.19  # update de totale prijs
timbitsResterend = timbitsResterend - 40 * groteDozen  # reken uit hoeveel timbits we nog steeds nodig hebben

print('groteDozen is gelijk aan', groteDozen)
print('totaleKosten is gelijk aan', totaleKosten)
print('timbitsResterend is gelijk aan', timbitsResterend)

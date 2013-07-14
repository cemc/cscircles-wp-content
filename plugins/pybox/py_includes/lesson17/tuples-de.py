T = (3, 4, 5)
print(T)
print(type(T))           # tuple
print(T[0])              # Das erste Element des Tupels
print(list(T))           # Tupel in Liste konvertieren
print(tuple([1, 2, 3]))  # Liste in Tupel konvertieren
T[0] = "three"           # Fehler! Tupelwerte können nicht verändert werden

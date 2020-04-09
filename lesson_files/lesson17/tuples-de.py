T = (3, 4, 5)
print(T)
print(type(T))           # 'tuple'
print(T[0])              # das erste Element im Tupel
print(list(T))           # Tupel in Liste umwandeln
print(tuple([1, 2, 3]))  # Liste in Tupel umwandeln
T[0] = "three"           # Fehler! Tupelwerte können nicht geändert werden

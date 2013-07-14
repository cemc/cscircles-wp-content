oldSize = ["letter", 8.5, 11]   # Größe von Papier in Zoll
newSize = oldSize               # Kopie
newSize[1] = newSize[1]*2.54    # in cm konvertieren
newSize[2] = newSize[2]*2.54    # nochmal
print(newSize)                  # wie erwartet ["letter", 21.59, 27.94]
print(oldSize)                  # Überraschung! nicht ["letter", 8.5, 11]

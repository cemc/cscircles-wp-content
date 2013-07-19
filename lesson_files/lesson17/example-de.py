oldSize = ["letter", 8.5, 11]   # Größe eines Papiers in Zoll
newSize = oldSize               # erstelle eine Kopie
newSize[1] = newSize[1]*2.54    # konvertiere in cm
newSize[2] = newSize[2]*2.54    # konvertiere in cm
print(newSize)                  # Ausgabe, wie erwartet: ["letter", 21.59, 27.94]
print(oldSize)                  # Überraschung! Ausgabe ist NICHT: ["letter", 8.5, 11]

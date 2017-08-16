oldSize = ["letter", 8.5, 11]   # rozmiar papieru w centymetrach
newSize = oldSize               # tworzy kopię?
newSize[1] = newSize[1]*2.54    # konwersja na cm
newSize[2] = newSize[2]*2.54    # konwersja to cm
print(newSize)                  # zgodnie z oczekiwaniami, drukuje ["letter", 21.59, 27.94]
print(oldSize)                  # nie zgodnie z oczekiwaniami, numery zostały zmienione ["letter", 8.5, 11]...

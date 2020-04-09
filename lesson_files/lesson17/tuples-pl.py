T = (3, 4, 5)
print(T)
print(type(T))           # tuple (krotki)
print(T[0])              # pierwszy element tupli
print(list(T))           # convwersja tupli na listę
print(tuple([1, 2, 3]))  # conwersja listy na tuplę
T[0] = "three"           # Error! Nie możesz zmienić wartości tupli

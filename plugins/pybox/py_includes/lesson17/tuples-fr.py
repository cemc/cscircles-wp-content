T = (3, 4, 5)
print(T)
print(type(T))           # tuple
print(T[0])              # le premier élément du tuple
print(list(T))           # conversion d'un tuple à une liste
print(tuple([1, 2, 3]))  # conversion d'une liste à un tuple
T[0] = "trois"           # Erreur! On ne peut pas modifier valeurs des tuples

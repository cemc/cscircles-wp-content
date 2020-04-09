T = (3, 4, 5)
print(T)
print(type(T))           # tuple
print(T[0])              # the first item in the tuple
print(list(T))           # converting a tuple to a list
print(tuple([1, 2, 3]))  # converting a list to a tuple
T[0] = "three"           # Error! You can't change tuple values

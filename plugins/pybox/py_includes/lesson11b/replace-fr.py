def xRemplace(valeur):
    global x
    x = valeur

x = "extérieur"
xRemplace("intérieur")
print(x)          # cette fois, la sortie est 'intérieur'!

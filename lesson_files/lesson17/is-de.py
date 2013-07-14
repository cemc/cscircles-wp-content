L = ['text', 11]
LAgain = L             # neue Referenz auf L
print(LAgain is L)     # Selbe Identität? Ja
LCopy = L[:]           # Kopie erzeugen
print(LCopy == LAgain) # Selbe Werte?   Ja, beide sind ['text', 11]
print(LCopy is LAgain) # Selbe Identität? Nein: LAgain ist L, aber LCopy nicht

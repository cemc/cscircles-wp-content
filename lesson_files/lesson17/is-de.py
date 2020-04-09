L = ['text', 11]       # L verweist auf die Liste ['text',11]
LAgain = L             # LAgain verweist nun auf dieselbe Liste.
print(LAgain is L)     # Identischer Verweis? Ja
LCopy = L[:]           # Kopie erstellen
print(LCopy == LAgain) # Gleiche Werte? Ja, LCopy und L verweisen beide auf Listen ['text', 11]
print(LCopy is LAgain) # Identischer Verweis? Nein: LAgain "is" L, aber LCopy nicht.

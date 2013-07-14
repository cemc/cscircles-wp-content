# Wie schwer ist es mit einem 2D-Gitter in Python zu arbeiten?
#   (z.B., bestimme die
#  Anzahl der Räume in einem Schloss mit Mauern dargestellt als '.')

# Zeichenketten können in Python nicht editiert werden, also nehmen wir eine Liste von Listen

# Lesbares Gitter: Liste von Zeichenketten
g = [ '..........',     
      '.oooo.ooo.',
      '......o.o.',
      '.oooo.ooo.',
      '..oo..ooo.',
      '..o...oo..',
      '..........'  ]

# Liste von Zeichenketten in Liste von Listen konvertieren
g2 = []
for i in range(len(g)):
  g2.append(list(g[i]))

# Mit dem Gitter arbeiten (z.B. jede Region einfärben)

# Ausgabe?
print(g2) #bah
for i in range(len(g2)): print(g2[i]) #immernoch unschön
for row in range(len(g2)): print(''.join(g2[row]))

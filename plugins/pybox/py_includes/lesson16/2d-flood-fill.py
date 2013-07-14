# How difficult is it to work with 2D grids in Python? 
#   (E.g., determine the
#  number of rooms in a castle where walls are given by '.')

# Python strings cannot be modified, so we use a list of lists.

# Readable grid: list of strings 
g = [ '..........',     
      '.oooo.ooo.',
      '......o.o.',
      '.oooo.ooo.',
      '..oo..ooo.',
      '..o...oo..',
      '..........'  ]

# Convert list of strings to list of lists
g2 = []
for i in range(len(g)):
  g2.append(list(g[i]))

# Do operations on grid. e.g. label each connected region with a different colour

# How to print?
print(g2) #yuck
for i in range(len(g2)): print(g2[i]) #still yucky
for row in range(len(g2)): print(''.join(g2[row]))

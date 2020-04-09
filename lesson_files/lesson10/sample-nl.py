def binnenkant(string, breedte):
  return string + " " * (breedte - len(string))

def buitenkant(string1, string2):
  w = max(len(string1), len(string2))
  print("*" * (w + 4))
  print("* " + binnenkant(string1, w) + " *")
  print("* " + binnenkant(string2, w) + " *")
  print("*" * (w + 4))

buitenkant("Doos nummer 1", "is erg leuk")
buitenkant("Doos #2", "(not False) is " + str(not False))

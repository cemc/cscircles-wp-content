def inner(łańcuch, szerokość):
  return łańcuch + " " * (szerokość - len(łańcuch))

def outer(łańcuch1, łańcuch2):
  w = max(len(łańcuch1), len(łańcuch2))
  print("*" * (w + 4))
  print("* " + wewnętrzny(łańcuch1, w) + " *")
  print("* " + wewnętrzny(łańcuch2, w) + " *")
  print("*" * (w + 4))

zewnętrzny("Pudełko #1", "jest bardzo dobrze")
zewnętrzny("Pudełko #2", "(not False) jest " + str(not False))

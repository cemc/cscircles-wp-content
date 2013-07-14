def dedans(chaine, largeur):
  return chaine + " " * (largeur - len(chaine))

def dehors(chaine1, chaine2):
  z = max(len(chaine1), len(chaine2))
  print("*" * (z + 4))
  print("* " + dedans(chaine1, z) + " *")
  print("* " + dedans(chaine2, z) + " *")
  print("*" * (z + 4))

dehors("Cette boite", "a des côtés droits")
dehors("Boites adieu!", "(not False) est " + str(not False))

def inner(string, width):
  return string + " " * (width - len(string))

def outer(string1, string2):
  w = max(len(string1), len(string2))
  print("*" * (w + 4))
  print("* " + inner(string1, w) + " *")
  print("* " + inner(string2, w) + " *")
  print("*" * (w + 4))

outer("Box number one", "is very fun")
outer("Box #2", "(not False) is " + str(not False))

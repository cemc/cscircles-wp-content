def inner(string, width):
  return string + " " * (width - len(string))

def outer(string1, string2):
  w = max(len(string1), len(string2))
  print("*" * (w + 4))
  print("* " + inner(string1, w) + " *")
  print("* " + inner(string2, w) + " *")
  print("*" * (w + 4))

outer("一号箱子", "是十分有趣的")
outer("箱子 #2", "(不是FALSE) 是 " + str(not False))

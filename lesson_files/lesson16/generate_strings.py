def generate_strings(prefix, length):
  if length == 0:
    print(prefix)
  else:
    generate_strings(prefix + '0', length - 1)
    generate_strings(prefix + '1', length - 1)

generate_strings('', 3)

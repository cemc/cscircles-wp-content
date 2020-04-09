def factorial(n):
  result = n
  if n > 1: result = result * factorial(n - 1)
  return result

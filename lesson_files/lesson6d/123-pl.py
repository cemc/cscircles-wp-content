timbitsLeft = int(input()) # krok 1: wprowadzenie danych wejściowych

print('the input is', timbitsLeft)

totalCost = 0              # krok 2: zainicjowanie zmiennej totalCost (nadanie jej wartości początkowej)

# krok 3: kupowanie dużych pudełek, ile się da
bigBoxes = timbitsLeft / 40
totalCost = totalCost + bigBoxes * 6.19    # aktualizacja całkowitego kosztu
timbitsLeft = timbitsLeft - 40 * bigBoxes  # ile jeszce potrzebujemy timbitów?

print('bigBoxes equals', bigBoxes)
print('totalCost equals', totalCost)
print('now timbitsLeft equals', timbitsLeft)

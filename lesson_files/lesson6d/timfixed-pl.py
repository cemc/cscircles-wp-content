timbitsLeft = int(input()) # krok 1: wprowadzenie danych wejściowych
totalCost = 0              # krok 2: zainicjowanie zmiennej totalCost (nadanie jej wartości początkowej)

# krok 3: kupowanie dużych pudełek, ile się da
bigBoxes = int(timbitsLeft / 40)
totalCost = totalCost + bigBoxes * 6.19    # aktualizacja całkowitego kosztu
timbitsLeft = timbitsLeft - 40 * bigBoxes  # ile jeszce potrzebujemy timbitów?

if timbitsLeft >= 20:                # krok 4, czy możemy kupić średnie pudełka?
    totalCost = totalCost + 3.39
    timbitsLeft = timbitsLeft - 20
if timbitsLeft >= 10:                # krok 5, czy możemy kupić małe pudełka?
    totalCost = totalCost + 1.99
    timbitsLeft = timbitsLeft - 10

totalCost = totalCost + timbitsLeft * 0.20 # krok 6
print(totalCost)                         # krok 7

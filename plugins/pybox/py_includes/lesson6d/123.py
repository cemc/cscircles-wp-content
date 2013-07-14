timbitsLeft = int(input()) # step 1: get the input

print('the input is', timbitsLeft)

totalCost = 0              # step 2: initialize the total cost

# step 3: buy as many large boxes as you can
bigBoxes = timbitsLeft / 40
totalCost = totalCost + bigBoxes * 6.19    # update the total price
timbitsLeft = timbitsLeft - 40 * bigBoxes  # calculate timbits still needed

print('bigBoxes equals', bigBoxes)
print('totalCost equals', totalCost)
print('now timbitsLeft equals', timbitsLeft)

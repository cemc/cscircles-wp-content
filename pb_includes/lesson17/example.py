oldSize = ["letter", 8.5, 11]   # size of paper in inches
newSize = oldSize               # make a copy
newSize[1] = newSize[1]*2.54    # convert to cm
newSize[2] = newSize[2]*2.54    # convert to cm
print(newSize)                  # as expected, prints ["letter", 21.59, 27.94]
print(oldSize)                  # surprise! DOES NOT print ["letter", 8.5, 11]

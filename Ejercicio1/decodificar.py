import csv

def decode_score(digits: str, encoded_str: str) -> int:
    """
    Convert an encoded string of custom digits to an integer,
    using the provided 'digits' as the numeral system.
    """
    base = len(digits)
    # Map each character to its numeric value (its index in 'digits')
    digit_map = {ch: i for i, ch in enumerate(digits)}
    
    result = 0
    for ch in encoded_str:
        result = result * base + digit_map[ch]
    return result

def main():
    # Open the CSV file containing scores
    with open('puntuaciones.csv', newline='', encoding='utf-8') as csv_file:
        csv_reader = csv.reader(csv_file)
        results = []
        for username, digits, encoded_str in csv_reader:
            decoded_score = decode_score(digits, encoded_str)
            results.append((username, decoded_score))
    
    # Print each username with its decoded score in the order of the input
    for username, decoded_score in results:
        print(f"{username},{decoded_score}")

if __name__ == "__main__":
    main()
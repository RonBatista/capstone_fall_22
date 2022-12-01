import sys
import os
import shutil
import time

def main():
    zip_file_path_noExt = sys.argv[1] #grabs system arguments containing the path to the temp results dirtectory and zipfile downloaded by the client
    print(zip_file_path_noExt) #code here is similar to setup of deleteTempResults.py
    zip_file_path = sys.argv[2]
    print(zip_file_path)

    if (os.path.exists(zip_file_path_noExt)): #difference lies here where the file is not deleted
        print("The temp results directory and zip file have already been generated, you can download the generated results.")
    else:
        print("The temp results directory and zip file do not exist!")

if __name__ == "__main__":
    main()

import sys
import os
import shutil
import time

def main():
    zip_file_path_noExt = sys.argv[1]       #grabs system arguments containing the path to the temp results dirtectory and zipfile downloaded by the client
    print(zip_file_path_noExt)
    zip_file_path = sys.argv[2]
    print(zip_file_path)

    #t = 604800  # a week in seconds
    t = 120 #timer test
    while t:  #loop creates timer to keep data in data in database for a week before deleting
       mins, secs = divmod(t, 60)
       timer = '{:02d}:{:02d}'.format(mins, secs)
       time.sleep(1)
       t -= 1 # reduces t variable

    if (os.path.exists(zip_file_path_noExt)):   #if these paths exist, delete them
        shutil.rmtree(zip_file_path_noExt)
        os.remove(zip_file_path)
        print("The temp results directory and zip file were deleted successfully!")
    else:
        print("The temp results directory and zip file do not exist!")

#---------------------------------------------------------------------------------------------------------------------Run Main
if __name__ == "__main__":
    main()

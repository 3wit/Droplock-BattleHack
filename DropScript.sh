#!/bin/bash
#Created by Ethan Wessel, Israel Torres, Brandon, Whitney
#Battlehack 2015 - March - 1

#variables
dropDir=$HOME/Dropbox/Apps/.Droplock
airport="/System/Library/PrivateFrameworks/Apple80211.framework/Versions/A/Resources/airport -I"
isLockReleased="SERVER PATH"
isPaidFor="SERVER PATH"
grabRecentFiles="SERVER PATH"
paymentMethods="SERVER PATH"
#Year - Month - Day - Time
dateTime=$(date +"%Y-%m-%d_%H.%M.%S")
getAirportCall="$airport > $dropDir/NetworkInfo_$dateTime.txt"
getExternalIPCall="curl ipinfo.io/json >> $dropDir/NetworkInfo_$dateTime.txt"
warmupTime=1.00

#SUMMARY:
# This Script gathers information on a user who has compromised your system
# It collects the current network they are connected to
# It collects their general location and external ip
# A message is loaded to allow the system to be purchased

SetupDirectories(){
	if [ ! -d $dropDir ]
	then 
		mkdir $HOME/Dropbox/Apps
		# we dont make the .Droplock folder because that is handled by Dropbox
		mkdir $dropDir
		mkdir $dropDir/Images
		mkdir $dropDir/Logs
		#imagesnap lives in local folder for now
		cp ~/Droplock/imagesnap	$dropDir
	fi
}

TakePicture(){
	#TAKE PICTURE
	rm $dropDir/*.png
	eval $dropDir/imagesnap -w $warmupTime $dropDir/mugshot_$dateTime.png
	cp $dropDir/*.png $dropDir/Images
	mv $dropDir/mugshot_$dateTime.png $dropDir/mugshot.png	#rename the timestamped file on top level
}

TakeLog(){
	#TAKE LOG
	rm $dropDir/*.txt
	eval $getAirportCall
	eval $getExternalIPCall
	cp $dropDir/NetworkInfo_$dateTime.txt $dropDir/Logs
	mv $dropDir/NetworkInfo_$dateTime.txt $dropDir/NetworkInfo.txt
}

HandleUserPaid(){
	#STOP the shutdown
	#$(sudo killall shutdown)
	#Remove all your data / clear laptop / remove watch process
	#rm -rf $dropDir / system data / etc...
	echo "user has paid: stop shutdown, remove and clear data, unload process"
}

#====================================================================

# If the directories dont exist we create them
SetupDirectories

#Check with our server if the system has been marked as compromised
if [ $(curl $isLockReleased) == "false" ]
then
	echo "lock was activated"
	
	#SHUTDOWN happens after x of minutes
	#-k flag says not real
	#$(shutdown -k +30 "Failure to Comply") 
	
	echo "opening browser site"
	#open $paymentMethods

	TakePicture
	TakeLog
	
	#NOTIFY SERVER
	sleep 5 #we sleep to ensure all files are been synced with dropbox first
    curl $grabRecentFiles

else
	echo "lock deactivated"
	
	#IF USER HAS PAID
	if [ $(curl $isPaidFor) == "true" ]
	then
		HandleUserPaid
	fi
fi

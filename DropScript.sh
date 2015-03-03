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

#SUMMARY:
# This Script gathers information on a user who has compromised your system
# It collects the current network they are connected to
# It collects their general location and external ip
# A message is loaded to allow the system to be purchased

#SETUP PHASE:
# if the directories dont exist we create them
if [ ! -d $dropDir ]
then 
	mkdir $HOME/Dropbox/Apps
	mkdir $dropDir
	mkdir $dropDir/Images
	mkdir $dropDir/Logs
	#imagesnap lives in local folder for now
	cp ~/Droplock/imagesnap	$dropDir
fi

#Check with our server if the system has been marked as compromised
if [ $(curl $isLockReleased) == "false" ]
then

	echo "lock was activated"
	
	#SHUTDOWN happens after x of minutes
	#-k flag says not real
	#$(shutdown -k +30 "Failure to Comply") 
	
	echo "opening browser site"
	open $paymentMethods
	
	#Get date and time for file names
	#Year - Month - Day - Time
	dateTime=$(date +"%Y-%m-%d_%H.%M.%S")

	#Datetime exists here...
	getAirportCall="$airport > $dropDir/NetworkInfo_$dateTime.txt"
	getExternalIPCall="curl ipinfo.io/json >> $dropDir/NetworkInfo_$dateTime.txt"

	#TAKE PICTURE
	rm $dropDir/*.png
	eval $dropDir/imagesnap $dropDir/mugshot_$dateTime.png
	cp $dropDir/*.png $dropDir/Images
	mv $dropDir/mugshot_$dateTime.png $dropDir/mugshot.png	#rename the timestamped file on top level 

	#TAKE LOG
	rm $dropDir/*.txt
	eval $getAirportCall
	eval $getExternalIPCall
	cp $dropDir/NetworkInfo_$dateTime.txt $dropDir/Logs
	mv $dropDir/NetworkInfo_$dateTime.txt $dropDir/NetworkInfo.txt
	
	#NOTIFY SERVER
	sleep 5 #we sleep to ensure all files are been synced
    curl $grabRecentFiles

else
	echo "lock deactivated"
	
	#IF USER HAS PAID
	if [ $(curl $isPaidFor) == "true" ]
	then
		#STOP the shutdown
		#$(sudo killall shutdown)
		#Remove all your data / clear laptop / remove watch process
		#rm -rf $dropDir / system data / etc...
		echo "stop shutdown, remove and clear data, unload process"
	fi
fi

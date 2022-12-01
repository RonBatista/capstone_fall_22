import os, sys
import glob
import rs2
import numpy as np
import operator
from osgeo import gdal, gdalnumeric, ogr, osr

# main function is used to debug and test the file (running it from the server)
def main():
	#run function with dummy data if ran from server
	get_cv(32614, "/var/www/html/uas_data/uploads/products/2021_Corpus_Christi_Cotton_and_Corn/10/28/2021/SHAPE/2021_cc_brewer_plot_boundary_map_maturity_trial/2021_cc_brewer_plot_boundary_map_maturity_trial.shp", "/var/www/html/uas_data/uploads/products/2021_Corpus_Christi_Cotton_and_Corn/Phantom_4_Pro/RGB/04-08-2021/20210408/CHM/20210408_rgb_chm/20210408_rgb_chm.tif", "/home/ubuntu/web/uas_data/download/product/2021_Corpus_Christi_Cotton_and_Corn/20210408_cc_p4r_parking_mosaic", "")

#-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
# Name: get_cv
# Function: generates Canopy Volume attribute and saves the results to its respect to what project/ orthomosiac are selected.
# Parameters: epsg - EPSG value of the selected orthomosiac
#             shp - path to the selected shape/boundary file selected
#             chm - path to the selected Canopy Height Model (CHM) file selected
#             out_dir - path the othomosiac directory where the generated results are stored.

def get_cv(espg, shp, chm, out_dir, object_handle):
	print ("Generating CV plot")

	gdal.UseExceptions()

	# Coordinate system
	sproj = osr.SpatialReference()
	sproj.ImportFromEPSG(int(espg))

#	chms.sort()

	out_dir = os.path.join(out_dir, 'cv_boundary')
	if not os.path.exists(out_dir):
			os.mkdir(out_dir)

	out_cv = os.path.join(out_dir, ('cv_boundary_' + os.path.basename(shp)))

	print ("Crop Shape")
	## shapefile open
	driver = ogr.GetDriverByName('ESRI Shapefile') #file type
	shapef = driver.Open(shp, 1)
	lyr = shapef.GetLayer()
	spatialRef = lyr.GetSpatialRef() # Get projection

	## Create the output shapefile
	outDriver = ogr.GetDriverByName('ESRI Shapefile')

	if os.path.exists(out_cv):
		outDriver.DeleteDataSource(out_cv)

	outDataSource_cc = outDriver.CreateDataSource(out_cv)
	outLayer_cc = outDataSource_cc.CopyLayer(lyr, "agrilife")
	out_fn_prj_cc = os.path.join(out_dir, os.path.splitext(out_cv)[0] + '.prj')

#	spatialRef.MorphToESRI()
#	file = open(out_fn_prj_cc, 'w')
#	file.write(spatialRef.ExportToWkt())
#	file.close()

	outDataSource_cc = None
	shapef = None

	# Create an OGR layer from a boundary shapefile
	driver = ogr.GetDriverByName('ESRI Shapefile') #file type
	shapef_out_cc = driver.Open(out_cv, 1)
	ccLayer = shapef_out_cc.GetLayer()

	#ch_mean_defn = []
	#for fn in chms:
		#basename = os.path.basename(fn)
		#date_str = basename.split("20",1)[1].split("_",1)[0]
		#ch_mean_defn.append(ogr.FieldDefn('avCH'+date_str, ogr.OFTReal))

	#ch_max_defn = []
	#for fn in chms:
		#basename = os.path.basename(fn)
		#date_str = basename.split("20",1)[1].split("_",1)[0]
		#ch_max_defn.append(ogr.FieldDefn('mxCH'+date_str, ogr.OFTReal))
	#Added
	#ch_90_defn = []
	#for fn in chms:
		#basename = os.path.basename(fn)
		#date_str = basename.split("20",1)[1].split("_",1)[0]
		#ch_90_defn.append(ogr.FieldDefn('90CH'+date_str, ogr.OFTReal))
	#Added
	#ch_95_defn = []
	#for fn in chms:
		#basename = os.path.basename(fn)
		#date_str = basename.split("20",1)[1].split("_",1)[0]
		#ch_95_defn.append(ogr.FieldDefn('95CH'+date_str, ogr.OFTReal))

	#ch_sd_defn = []
	#for fn in chms:
		#basename = os.path.basename(fn)
		#date_str = basename.split("20",1)[1].split("_",1)[0]
		#ch_sd_defn.append(ogr.FieldDefn('sdCH'+date_str, ogr.OFTReal))

	cv_defn = []
#	for fn in chms:
	basename = os.path.basename(chm)
	date_str = basename.split("20", 1)[1].split("_",1)[0]
	cv_defn.append(ogr.FieldDefn('20' + date_str, ogr.OFTReal))
	#cv_defn.append(ogr.FieldDefn('20'+date_str, ogr.OFTReal))

	#for tt in ch_mean_defn:
	    #ccLayer.CreateField(tt)

	#for tt in ch_max_defn:
	    #ccLayer.CreateField(tt)

	#Added
	#for tt in ch_90_defn:
	    #ccLayer.CreateField(tt)
	#Added

	#for tt in ch_95_defn:
	    #ccLayer.CreateField(tt)

	#for tt in ch_sd_defn:
	    #vccLayer.CreateField(tt)

	for tt in cv_defn:
	    ccLayer.CreateField(tt)

	#for i in range(0, len(chms)):
#	for i in range(len(chms)):# if no number means all files

	#print "Multi Processing (%d/%d) [%.2f]" % (i+1, len(chms), float(i+1) / len(chms) * 100.0)

	# Create an OGR layer from a boundary shapefile
	driver = ogr.GetDriverByName('ESRI Shapefile') #file type
	shapef_out_cc = driver.Open(out_cv, 1)
	ccLayer = shapef_out_cc.GetLayer()

#	chm_fn = chms[i]
	chm_fn = chm

	basename = os.path.basename(chm_fn)
	date_str = basename.split("20", 1)[1].split("_", 1)[0]

	print ("Image reading")
	chm_img = rs2.RSImage(chm_fn)

	print ("Extracting attribute")
	for crop_poly in ccLayer:

		geoTrans = chm_img.geotransform

		clipped_chm = chm_img.clip_by_polygon(crop_poly)

		filtered_chm = clipped_chm[0,:,:]
		filtered_chm = filtered_chm[np.nonzero(filtered_chm)]
		filtered_chm = filtered_chm[~np.isnan(filtered_chm)]
		filtered_chm = filtered_chm[~np.isinf(filtered_chm)]

		if filtered_chm.size == 0:
			#ch_mean = 0
			#ch_max = 0
			#Added
			#ch_90 = 0
			#Added
			#ch_95 = 0
			#ch_sd = 0
			cv = 0
		else:
			#ch_mean = np.mean((filtered_chm))
			#ch_max = np.max((filtered_chm))
			#Added
			#ch_90 = np.percentile((filtered_chm), 90)
			#Added
			#ch_95 = np.percentile((filtered_chm), 95)
			#ch_sd = np.std((filtered_chm))
			cv = np.sum((filtered_chm)) * geoTrans[1] * geoTrans[1]

			#crop_poly.SetField('avCH'+date_str, float(ch_mean))
			#crop_poly.SetField('mxCH'+date_str, float(ch_max))
			#Added
			#crop_poly.SetField('90CH'+date_str, float(ch_90))
			#Added
			#crop_poly.SetField('95CH'+date_str, float(ch_95))
			#crop_poly.SetField('sdCH'+date_str, float(ch_sd))
			crop_poly.SetField('20' + date_str, float(cv))
			#crop_poly.SetField('20'+date_str, float(cv))

			ccLayer.SetFeature(crop_poly)

			chm_img = None

	gdal.ErrorReset()
	shapef_out_cc = None


if __name__ == "__main__":
    main()
